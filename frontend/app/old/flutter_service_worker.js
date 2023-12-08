'use strict';
const MANIFEST = 'flutter-app-manifest';
const TEMP = 'flutter-temp-cache';
const CACHE_NAME = 'flutter-app-cache';
const RESOURCES = {
  ".well-known/microsoft-identity-association.json": "fb04932fe2e092c412d84459f7d99e2c",
"assets/AssetManifest.json": "f6b3a51e6007fbc121dfd942d85a5a58",
"assets/assets/applogo/google_signin_button.png": "75a7cb04887751c08cba916885e3b5fd",
"assets/assets/applogo/ic_logo.png": "eac52bc6e429f9d5cc9b8d0db43c6dc6",
"assets/assets/applogo/progress_logo.gif": "02adc112155eee6f5cfa111061cdc56a",
"assets/assets/applogo/voice_download.gif": "6d1585bf7b168658f620120ec58aae91",
"assets/assets/ca/lets-encrypt-r3.pem": "9b8740c5387a2fd70006d3cbf2229a0c",
"assets/assets/guide/user_guide_ar.json": "af4135eede4ce66be62267096a47d72f",
"assets/assets/guide/user_guide_bn.json": "7e6b62aa7f7ca6d7bcac884de7b6d6c5",
"assets/assets/guide/user_guide_ca.json": "4d3e784b33ec4ef640c238d2633d07a5",
"assets/assets/guide/user_guide_cs.json": "2f64f57ffd12d328177b24686094abe2",
"assets/assets/guide/user_guide_da.json": "0db8ca85c4bb09c2ae7c78c93c7bce42",
"assets/assets/guide/user_guide_de.json": "24bba6a97bf9b0550867c7904f6d5c9b",
"assets/assets/guide/user_guide_el.json": "24bba6a97bf9b0550867c7904f6d5c9b",
"assets/assets/guide/user_guide_en.json": "a117353bbb485e2f4a495c4c41516c97",
"assets/assets/guide/user_guide_es.json": "c212d3fafccfb914ef6e46d14924c905",
"assets/assets/guide/user_guide_et.json": "ec402daacd4375cfc0a9f3327bf1b9ef",
"assets/assets/guide/user_guide_fa.json": "a8d953a98af48cf773d12cf96b9a7bca",
"assets/assets/guide/user_guide_fi.json": "40db9d6b34876bfaa508be8132376e90",
"assets/assets/guide/user_guide_fr.json": "7638b42d01a011a79b4ce86aaf76ff30",
"assets/assets/guide/user_guide_gu.json": "9953b8a648d274a57fd81bbaca233f54",
"assets/assets/guide/user_guide_hi.json": "b435ba7bdb9ea30e3a9713620c1140af",
"assets/assets/guide/user_guide_hr.json": "97983c304e7f3880b8f698a22bbf5db6",
"assets/assets/guide/user_guide_hu.json": "cdf406af8e7372d8f49ca1922278bf35",
"assets/assets/guide/user_guide_it.json": "fa9820e8f8ecf036712295fe40b6e745",
"assets/assets/guide/user_guide_ja.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_kn.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_lt.json": "247e738a5b5bc8c496586e687cdc5ad3",
"assets/assets/guide/user_guide_ml.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_mr.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_nl.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_no.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_pa.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_pl.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_pt.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_ro.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_ru.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_sv.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_ta.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_te.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_tr.json": "afd347167825f90a53274cee16d75264",
"assets/assets/guide/user_guide_ur.json": "afd347167825f90a53274cee16d75264",
"assets/assets/images/account.svg": "7f10d977049fb678b93d39ad59de4818",
"assets/assets/images/add_member.svg": "be04990bb6749092fccfd8e25bf82ed4",
"assets/assets/images/add_project.svg": "eefccfabab1726f12d10c1088b114389",
"assets/assets/images/add_task.svg": "e60bd843f5296728cb33fdab6503b930",
"assets/assets/images/apple_logo.png": "b428a64f8a4a69162a124860e4db5f42",
"assets/assets/images/appstore.png": "e8ab4980bcf7098bbb1970ca575220e8",
"assets/assets/images/appsumo.svg": "662bb1e5f15e2ae2f727d4865dda8282",
"assets/assets/images/attachment.svg": "e7f319f8f693a97acaad3977cd64b746",
"assets/assets/images/attach_file_square_icon.svg": "e1f0943ae807c661334ac7acf3558fe7",
"assets/assets/images/avatar_eye_blink.png": "dfbfde3f1eb4c516d54b7ac48e1b9dc8",
"assets/assets/images/avatar_lip_sync.png": "ebabe71c0afd502be0181e1acf7df9b9",
"assets/assets/images/board_icon.svg": "2e6f6164655bd9a013047422aab216ce",
"assets/assets/images/calendar_date.svg": "7f6ce7f2bdbe6d1ef79683492e6b7f76",
"assets/assets/images/calendar_icon.svg": "66ef3ce885c9754d8e0bf64fb48a777d",
"assets/assets/images/calender.svg": "da789a2ad6d7fe251b3a3d6500f2e0f4",
"assets/assets/images/camera.svg": "3e16c35232444cc4395f2b01933d2acc",
"assets/assets/images/check_icon.svg": "c61f16241302272da113e0e629471440",
"assets/assets/images/create_new_task.svg": "e70039a7391b64447263f693f1b4f2ba",
"assets/assets/images/cross_icon.svg": "5bb3e6b8f3ecce328ce11e5016b4e346",
"assets/assets/images/customer_plus_icon.svg": "e71445ca926b476b1fc7b71d0d3bd31c",
"assets/assets/images/date_time.svg": "3db011990824e00a002248000d94da72",
"assets/assets/images/dealfuel.png": "3e21c34e87e03ec379ff99646291d5b9",
"assets/assets/images/delayed.png": "a9f77228a1635dee5c47c884e1108b95",
"assets/assets/images/delete.svg": "360d3c5aa98e4a49abadcacd4a385839",
"assets/assets/images/dot.svg": "1d6c4659bd15bee8293849a7d9c7c944",
"assets/assets/images/download.svg": "564593abb399c2d01336ef2cd1cefc82",
"assets/assets/images/down_icon.svg": "48ed19eb3b8cd2f8df3ed165939847c0",
"assets/assets/images/drawer_bg.png": "8f5a7c18e7a0e63127dc1131540a2128",
"assets/assets/images/dropdown_register.svg": "c525ec30b3e42c54fa7b579857727ab1",
"assets/assets/images/drop_down.svg": "b9996ad2e3323d3ff839da7e79c5dc64",
"assets/assets/images/drop_down_up.svg": "595a261ddfc62af6e3caa0656bdeac66",
"assets/assets/images/duplicate_task.svg": "f3e95a98c40534b08565e6d3ba668702",
"assets/assets/images/edit_icon.svg": "b3aab2a10dc9bf4b27f41e5d7ae7dfc3",
"assets/assets/images/edit_milestone.svg": "692d48be069fc166be0e4173f7b2cda6",
"assets/assets/images/empty_box.png": "b9ceaeab2882bb04f82335fadb7fa7ee",
"assets/assets/images/face_movement.png": "06c8a3ee8608aef342c99fe4e207289d",
"assets/assets/images/faq_plus_icon.svg": "4388d3304a4fb6d35f76eb38219d3c4a",
"assets/assets/images/favourite.svg": "6a55bb455a12822ecfff5613bf5112b5",
"assets/assets/images/favourite_yellow.svg": "4c2a554384e063e1a17a540ec2d47be2",
"assets/assets/images/filter.svg": "23c5d2d91c17d03533e3cbb4385b90a9",
"assets/assets/images/filter_icon.svg": "83190721333b40a38fc26d22aa33922d",
"assets/assets/images/google.png": "010856ffe44996dec7f208598f5fbfbe",
"assets/assets/images/google_calendar.svg": "83d87b51986033d2f979124e86c207f3",
"assets/assets/images/google_drive.svg": "161395a2d38bc0dd3b3f6436ac8f4608",
"assets/assets/images/google_maps.png": "20c59a685b822578dc3eafb56565bba3",
"assets/assets/images/google_mic.png": "fe0ce1768838b54d1b69c2af2a37aa69",
"assets/assets/images/grid_icon.svg": "664b8d95cd177c67e1f27ca8d346b89c",
"assets/assets/images/heart.png": "21c66e36cb87a180ef479a33fb3a08b1",
"assets/assets/images/ic_archived_items.svg": "d2b6a9dce367f85e1675da870387b76a",
"assets/assets/images/ic_back.svg": "29251e037acf42cf3a8fed2da7c159cc",
"assets/assets/images/ic_complete_task.svg": "21c9ad7e463cca3c415bcec5a8dbfd01",
"assets/assets/images/ic_cross.svg": "e5047126e2b991c13b334270dec0f9e7",
"assets/assets/images/ic_crown.png": "b91e3a55b83218dd9d0093e56f2a3989",
"assets/assets/images/ic_delayed_task.svg": "8f55dcc32f4bd7f1d8a91601c9226a84",
"assets/assets/images/ic_delete_icon.svg": "c383ac80df9ed5250d573a31f7a645ef",
"assets/assets/images/ic_edit_blue.svg": "24a6d13aecc360d2145576cca72fc6d3",
"assets/assets/images/ic_edit_icon.svg": "5bdb51abb09f2f3d08f09f50c9acbbb7",
"assets/assets/images/ic_favorite.svg": "4b15bfa7a34b17d100a2e6aeb61e97ec",
"assets/assets/images/ic_file_csv.svg": "a4f018b9bde05246c21bbefdf9de5764",
"assets/assets/images/ic_file_doc.svg": "c72dfa7018d295305acc5ec4666fc940",
"assets/assets/images/ic_file_pdf.svg": "3fa92281020f448a50d540d268e7fa3e",
"assets/assets/images/ic_file_ppt.svg": "6ef89bbbb8afea2e14fe40ee349daf7d",
"assets/assets/images/ic_file_txt.svg": "8983289887bd4940e52da65b0aa62b5c",
"assets/assets/images/ic_file_xls.svg": "d24ad3d52da606947b7beb7ded75ee09",
"assets/assets/images/ic_google.png": "ba80c58505b31b985a9017c14dd3daef",
"assets/assets/images/ic_grid.svg": "6f54e8f0f4424d9a60f3789f093cd39d",
"assets/assets/images/ic_guide.svg": "c4ceb900bd1b870afea667c7bbde2937",
"assets/assets/images/ic_home.svg": "b25759c4ef9a00b3bfa0a0c3ee09a91e",
"assets/assets/images/ic_listview.svg": "3bc4b00b62bc92ac80c595450583dd6c",
"assets/assets/images/ic_message.svg": "6817830a3f871ac47e89f196c3049823",
"assets/assets/images/ic_mic.svg": "c0b07741f7b5d1d5b674a4585ea6bc92",
"assets/assets/images/ic_milestone.png": "e16e1a7eef0e2eb49ee94e17b29140b2",
"assets/assets/images/ic_move.svg": "e4e7ee2bdfb64774d3c0610a301c49b9",
"assets/assets/images/ic_notification.svg": "83cdcc9cb93300d8e53ee5580dd0fd23",
"assets/assets/images/ic_organization.svg": "92b308a16568a775da6e7485c3144d08",
"assets/assets/images/ic_pause.svg": "ddb67ec97b8942c6dc227217df5a39ef",
"assets/assets/images/ic_play.svg": "d427673da7224f51f909eee9a6ff85ac",
"assets/assets/images/ic_plus.svg": "746600f824c1cf761d825564f53e8d8d",
"assets/assets/images/ic_profile.svg": "f0fd636672bea75936e95800fd893a4f",
"assets/assets/images/ic_reopen.svg": "90b4f064e078984e24e5e70e6289c943",
"assets/assets/images/ic_save.svg": "e9274f78ebda186369bcdb584d6e6e59",
"assets/assets/images/ic_search.svg": "61b4737a56e5f20e40c24d1715a6b367",
"assets/assets/images/ic_setting.svg": "98585daf0d62e7484bf9ec7cb6885776",
"assets/assets/images/ic_success.png": "b027f6ccbce7bb32666086e53b12c807",
"assets/assets/images/ic_task.svg": "94b31964c30fafe3eebced7fbff04d13",
"assets/assets/images/ic_team_member.svg": "2c1758ff6f1c7605bcdad5eebd686f3e",
"assets/assets/images/ic_view_icon.svg": "5e2aae1a182f402d6de31728ff953def",
"assets/assets/images/ic_voiceommands.svg": "96b90f596c45891dbaf87d53ef9c01da",
"assets/assets/images/ic_voice_details.svg": "685f22140d97f0f4c71f2d837cf0d52d",
"assets/assets/images/important_task.png": "c6abc53d26a73adfdaaa0cfb1f40b8be",
"assets/assets/images/key_board.svg": "4cc37157f53a0fd45eb37de2a2418ebd",
"assets/assets/images/leave_icon.svg": "4d535039d583805789b698441f8d4389",
"assets/assets/images/left_icon.svg": "4b059fa96df0015560c6965dd367836c",
"assets/assets/images/linkdin.png": "5271b350732d48e131bc8e91d861b23a",
"assets/assets/images/list_icon.svg": "e29d0bdba6cd53ad72ea56855ba95ac2",
"assets/assets/images/list_icon_project.svg": "3bc4b00b62bc92ac80c595450583dd6c",
"assets/assets/images/logout.svg": "f4c7b05bf446e001330dc534fe425dd9",
"assets/assets/images/logo_bot.png": "bcd778f8117fd95edeebaa5c9fc101e5",
"assets/assets/images/logo_project.png": "8b6f4fc616537cb2b1c46088f6ff29e4",
"assets/assets/images/manage_task.png": "97af38b85acc85d537e6fb45f1af7816",
"assets/assets/images/map.png": "892051bd1c555749efdb568e1f4b9f09",
"assets/assets/images/marketing_meetup.png": "08bbb32189612a296d5dbf6a33b33b0d",
"assets/assets/images/meeting_form.png": "c70cd2b277fbbb416f89da44bf8d0043",
"assets/assets/images/meeting_message.png": "4d2f320e8abff9358d914a3d657e9bc8",
"assets/assets/images/meeting_schedule_popup.png": "df2f66d5c345a31cf475d07587e46297",
"assets/assets/images/meeting_task.png": "1e2382870e055f6d1c8af22567eb11d5",
"assets/assets/images/meeting_task_popup.png": "5ea3abdd3efb0668a7dc63cda8329927",
"assets/assets/images/meeting_task_zoom.png": "bf8fe813d661fd92968d08300d777896",
"assets/assets/images/menu.svg": "79b6102136a118e05b41c07be4a198c1",
"assets/assets/images/milestone.png": "835f260d0c7f38c0e9bd166f2b1e9942",
"assets/assets/images/minus_icon.svg": "330956f92bece3fe9d452df32d95d550",
"assets/assets/images/more_three_dot.svg": "7c187062117455a5d69c9280b6229b29",
"assets/assets/images/move.svg": "3b164680cf0df76187fe66919fbd33f9",
"assets/assets/images/next_arrow.svg": "c85cbb71d072d6398938534e6752a369",
"assets/assets/images/no_attachment.svg": "ae9a0f02b76dacc2d6113248b66ff45b",
"assets/assets/images/no_conversion.svg": "117dee686dc20a623eef47e8f1cd2399",
"assets/assets/images/password_visibility.svg": "22aaf074d3800bda99e50c0de6af1ca9",
"assets/assets/images/pdf.png": "59a9691934fe11f2d0b7ed25c2ca5b20",
"assets/assets/images/pdf_icon.png": "59a9691934fe11f2d0b7ed25c2ca5b20",
"assets/assets/images/person.png": "b9b331bc8ca46ebb7a0a6539819b66e5",
"assets/assets/images/pin.png": "c66a0b35eb176be925912a021f2b07be",
"assets/assets/images/pitchground.png": "3705abddc22653ca71b69bc09c55bf11",
"assets/assets/images/playstore.png": "fc589825ca8528608e99bc9518edce94",
"assets/assets/images/plus_icon.svg": "fb7f862c8272ae1d8c3a58c95228a965",
"assets/assets/images/plus_rounded.png": "54ea835821f4fc0d60ec729e1b912808",
"assets/assets/images/popup_view.svg": "bcb7acec8318302115d2d8abbb00c5b4",
"assets/assets/images/post_new_project.png": "a550fd4f0a620555fdc1d3ae096007e8",
"assets/assets/images/post_new_project.svg": "0f7a64dc3759b196edb453cc30982cf2",
"assets/assets/images/profile.png": "a56284771e2e8c3dfcfd61a18c51c23e",
"assets/assets/images/profile_register.svg": "0237367ff71dffe56af24de12eae7b54",
"assets/assets/images/progress_milestone.png": "f5052dd775f123d7c124cc8b28fc411d",
"assets/assets/images/project.svg": "69da4a875402133f7568d2bc34550b9c",
"assets/assets/images/project_icon.svg": "69da4a875402133f7568d2bc34550b9c",
"assets/assets/images/project_name.svg": "842fd9952f59cbfba05443694c897ebc",
"assets/assets/images/punch.png": "75e002b875f56c37571a0df471d6cbf2",
"assets/assets/images/punch_pause.svg": "883ba2a97e447ec6aff010b98e3e3053",
"assets/assets/images/register_web_side.png": "b6f7b5dda73a68b7ac9dec9e1991e609",
"assets/assets/images/remind_me.svg": "e9d5ab2e65b544950097bdd5654799d1",
"assets/assets/images/remove_app.png": "ecc7e892467881ee01cc08920683b20f",
"assets/assets/images/restore_archive.svg": "76b926310f30fdd0752eba6c4b1e5f45",
"assets/assets/images/right_icon.svg": "ca00b18e35119cd6b69e60ca57d83e20",
"assets/assets/images/search.svg": "6c7c77af139791fddf9c3a1311d54925",
"assets/assets/images/search_icon.png": "03a5d7eaf67daa37f8de9a5924660a3e",
"assets/assets/images/share.svg": "80effaae77e0fdd624955edccb6fd536",
"assets/assets/images/success_created.gif": "a58eca87f55bfb6d2a09fc5a7e1b8eac",
"assets/assets/images/take_time.png": "2d4394f95a172729703277fe03e03277",
"assets/assets/images/task_details_attachment.svg": "de8e0aa6c21f444cd00772ca6723520a",
"assets/assets/images/task_details_gallery.svg": "b15e790504b0c883b756f5abc47b4cc0",
"assets/assets/images/task_details_pause.svg": "0d0d3b483662c1cac381588f6fbaf554",
"assets/assets/images/task_details_play.svg": "c697884c4d2182fdb3a3d6733c3f475f",
"assets/assets/images/task_details_plus.svg": "123aa2ac44141cb0b583118e0b0395ac",
"assets/assets/images/task_details_send.svg": "156ddb404b2c13f0bce4a5dcf62b1c5f",
"assets/assets/images/task_down_arrow.svg": "7f835c9c23720df8ad02198c18151fec",
"assets/assets/images/task_edit_pen.svg": "4758c14c42a63472865d6b6bc04d64df",
"assets/assets/images/task_icon.svg": "74c4b0bfcba686b030e776c8d81ddffe",
"assets/assets/images/task_list_pause.svg": "ba1200ba51c2056a3ea6b6cdcfddbcce",
"assets/assets/images/task_list_play.svg": "e5472daf3afab50f7a5efce6706d18ce",
"assets/assets/images/task_list_record.svg": "54e10a37b9f02038ad229504622651f5",
"assets/assets/images/task_list_stop.svg": "f19608104e976645d44ea8fe8dcba62b",
"assets/assets/images/task_time.svg": "42b53a7a9ecda3a84db6c3cca6b2b47e",
"assets/assets/images/task_timer.svg": "9015242cb2d654d4e5276eb913141882",
"assets/assets/images/timer_pause.svg": "649896a5798249ac972f5ab9716667d4",
"assets/assets/images/timer_play.svg": "ab727e233f8d64274050d1c686b633d7",
"assets/assets/images/uncomplete_task.png": "c3cebbf6fa43b22de8c3a42beb56abe8",
"assets/assets/images/upload_icon.svg": "87d33ed41312d1a472137cbd99c28ee7",
"assets/assets/images/upload_profile.svg": "87d33ed41312d1a472137cbd99c28ee7",
"assets/assets/images/url_icon.svg": "84fe9ecc0bb1b8f91495f25e133a89dc",
"assets/assets/images/video_call.svg": "ab57defd885393c58e65965820669739",
"assets/assets/images/web_login_bg.png": "c97bc29e0d106ff970594f23636a2c84",
"assets/assets/images/zoom_add.png": "7b5938c8fa4c8a72b75f878d2a2eb6b6",
"assets/assets/language/user_language_ar.json": "f9ff55977311e8a63b91d8ba3e28082b",
"assets/assets/language/user_language_bn.json": "4befe0c3787eeea68db99e8918ea5d5c",
"assets/assets/language/user_language_ca.json": "7321590701f516ed46de75855e225a16",
"assets/assets/language/user_language_cs.json": "2efe378aaa5604127029f56f8c563447",
"assets/assets/language/user_language_da.json": "0280dd56d89c37d3a1fa2451824455e6",
"assets/assets/language/user_language_de.json": "aad4ef3f47a927aa1bda9ba840f729a8",
"assets/assets/language/user_language_el.json": "23bb778288a46131d428d5ad5c6dbd2b",
"assets/assets/language/user_language_en.json": "6848a5d8c76728c9b7d6e5e4e9b48fcb",
"assets/assets/language/user_language_es.json": "8763100dc6ea8913ebdc6469881d9743",
"assets/assets/language/user_language_et.json": "898701a9df27b636f0491f0bc994af3b",
"assets/assets/language/user_language_fa.json": "b442606f0475e75bdcc459d516e7b876",
"assets/assets/language/user_language_fi.json": "0a2c6cebc9ec5029cedb8823822fc275",
"assets/assets/language/user_language_fr.json": "da1afe2e6554e5fd61ecf388777e0c1e",
"assets/assets/language/user_language_gu.json": "d2b6b7fbe014cf00e14fa3b51eb2ef45",
"assets/assets/language/user_language_hi.json": "5b5fcd57b65b64dca5d31395402f6f55",
"assets/assets/language/user_language_hr.json": "1cd3e6d7a30dd0e80cddac48e2d32fab",
"assets/assets/language/user_language_hu.json": "8ebfc89f2c297a50d7ae992aaa2ecf3f",
"assets/assets/language/user_language_it.json": "08c89e43f412d72d02089351647eaaa4",
"assets/assets/language/user_language_ja.json": "3844609d3372ba28cf0e466a22f34411",
"assets/assets/language/user_language_kn.json": "40d6ce219984ce0417653c7816af4482",
"assets/assets/language/user_language_lt.json": "30dc04a3472e51cb779ec658f9de85a7",
"assets/assets/language/user_language_mi.json": "2bb52e76beee97e88cda9a49a5e32299",
"assets/assets/language/user_language_mr.json": "376b7ed23a0614999460e403cb3fc6eb",
"assets/assets/language/user_language_nl.json": "4634708e12592aa08e410c5d71940427",
"assets/assets/language/user_language_no.json": "945eeea7aa8e59fd30296feb64d2f0d3",
"assets/assets/language/user_language_pa.json": "76aa5e2caf76fe6ff987e4f7aebe8edd",
"assets/assets/language/user_language_pl.json": "4a22578d1070ff5af7e0c3f55425bff3",
"assets/assets/language/user_language_pt.json": "457148f3162eab16ff2eed8c09b0c9ec",
"assets/assets/language/user_language_ro.json": "b5765a09ac2da6c209add8d4c654124f",
"assets/assets/language/user_language_ru.json": "a8bb43fa06f44f3dc8514ea0e9df681b",
"assets/assets/language/user_language_sv.json": "bb3a128a6e8a642f8d86953429fe5078",
"assets/assets/language/user_language_ta.json": "6975b5a7a6f9bd8f7d6630efadfba343",
"assets/assets/language/user_language_te.json": "05d6291a4af26ba2c0caff7605343b1d",
"assets/assets/language/user_language_tr.json": "4f6437fad66bbeb25ad1ecb6daeaa854",
"assets/assets/language/user_language_ur.json": "675007c6ae88d8c7ea9f8a71cac2d48d",
"assets/assets/mobile/add_attachment.png": "55d401806896fbc3dda3d8ef856d64e3",
"assets/assets/mobile/bottom_bg.png": "8060fafcc938ca36468845ec52ca4734",
"assets/assets/mobile/microsoftlogo.png": "149ff0322891b6e07f647a937298a7f8",
"assets/assets/mobile/notification.svg": "05e16d061e8e402a1c429967f803b451",
"assets/assets/mobile/refresh.svg": "caf2f9c73fc8bc2ff8df44f02819bbf8",
"assets/assets/mobile/top_bg.png": "b5cdab860aeec824c6218130eb34fb45",
"assets/assets/mobile/zoomimage.png": "ed0d6f6cf1ae3e7d7b1e1bd7f665bcd6",
"assets/assets/privacy_policy/privacy_policy.json": "23d221933b422723d54527122370c817",
"assets/assets/terms_condition/terms_condition.json": "06448a5533e744f1fd92e6eb7897a483",
"assets/assets/web/add_task.png": "466a9d6baaee3adbc1d2e8a66de3c0a7",
"assets/assets/web/calendar_auth.png": "2c7b9a01f69345a861397b0fca531385",
"assets/assets/web/calendar_task.png": "260f756dc4942406d9f9977f1b052f98",
"assets/assets/web/down_arrow.svg": "b9996ad2e3323d3ff839da7e79c5dc64",
"assets/assets/web/drawer_top_logo.png": "525aef9386931280d6989d5d39ccd3bf",
"assets/assets/web/favorite.svg": "b7d3930c8213bd4f53b591d5c831cc94",
"assets/assets/web/file_auth.png": "aaedf4d33a985cb86bfd4f270ca489cd",
"assets/assets/web/google_calendar_auth.png": "2c7b9a01f69345a861397b0fca531385",
"assets/assets/web/google_calendar_task.png": "260f756dc4942406d9f9977f1b052f98",
"assets/assets/web/google_drive_files.png": "64b8cf06201ee7dd7ca9d758aff0105b",
"assets/assets/web/google_login_auth.png": "aaedf4d33a985cb86bfd4f270ca489cd",
"assets/assets/web/google_meet_image.png": "d8d5b272e220d4e0b7cae1106806f3f3",
"assets/assets/web/ic_menu.svg": "05e4fb22a894de988400ee7c11851ed7",
"assets/assets/web/ic_todo_add.svg": "3845f4224c4666adc0a3a0d639a3e649",
"assets/assets/web/logo.png": "91bf0902e09f810e1c195d90a01adbe8",
"assets/assets/web/logo_login.png": "c08fc3c093574453a36e7c72409c2ba6",
"assets/assets/web/microsoft_logo.png": "149ff0322891b6e07f647a937298a7f8",
"assets/assets/web/move.svg": "4cc3d734cddad2e1ed2cc5dbce7599a6",
"assets/assets/web/play.svg": "e5472daf3afab50f7a5efce6706d18ce",
"assets/assets/web/pricing_1.png": "08fd839309f170fa4ccfeeb048ab350b",
"assets/assets/web/pricing_2.png": "b099f95637ac92881c80a1fadafb5f34",
"assets/assets/web/pricing_3.png": "e1270a1273938be6bf9083074d94883f",
"assets/assets/web/profile.svg": "66a3f662d57d72fa5e8d8798d342ad57",
"assets/assets/web/record_time.svg": "c1ac89a6cdb04d6ba0678cde3e88cbaf",
"assets/assets/web/report.svg": "e9ce83acaf369e430cba8053009d07fe",
"assets/assets/web/slide.svg": "4597337b52e5ef06b655787b37d6e63f",
"assets/assets/web/slide1.svg": "e33f4353f136d999d65ccb705764d9a5",
"assets/assets/web/slide2.svg": "fd60f7b3f71aed9e2446daf72c0161c6",
"assets/assets/web/timeline.svg": "3c88b5b660ae424b2a7222a7a44e60eb",
"assets/assets/web/user_google_login.png": "9fa1ac914750ae295f7ece5928ff32b2",
"assets/assets/web/web_bg.png": "9b25e81cc70cc43ae6c2d54470940797",
"assets/assets/web/web_list_drop_up.svg": "2f9d31a4c9e4a96645eba270f791fe91",
"assets/assets/web/zoom_image.png": "ed0d6f6cf1ae3e7d7b1e1bd7f665bcd6",
"assets/FontManifest.json": "9d7eb081cc979f2ca69c87af493cf30d",
"assets/fonts/MaterialIcons-Regular.otf": "95db9098c58fd6db106f1116bae85a0b",
"assets/fonts/Poppins-Bold.ttf": "a3e0b5f427803a187c1b62c5919196aa",
"assets/fonts/Poppins-Regular.ttf": "29cc97af5403e3251cbb586727938473",
"assets/NOTICES": "3cfec083d156cdf2ee2ad930f7e12fef",
"assets/packages/country_pickers/assets/ad.png": "8312ea200df9dd539e27c116939db42c",
"assets/packages/country_pickers/assets/ae.png": "7ff210c9d922e6047b30241b5176948b",
"assets/packages/country_pickers/assets/af.png": "4b0f402972e53c96146b67d621682070",
"assets/packages/country_pickers/assets/ag.png": "45b17f619e8d2d3321fe031c4a90691e",
"assets/packages/country_pickers/assets/ai.png": "7112379111bbf96dae31e0b13a62b926",
"assets/packages/country_pickers/assets/al.png": "675265e7b86d00e3aa6f25bf64a4dab9",
"assets/packages/country_pickers/assets/am.png": "55d71092c291a382a8fb4e0dae4b76a0",
"assets/packages/country_pickers/assets/an.png": "2aaab4636955c0e2609ad551e8e938cf",
"assets/packages/country_pickers/assets/ao.png": "eec240bde52c32770eeacd027b193347",
"assets/packages/country_pickers/assets/aq.png": "947030b9fb778b63ab28954c545ea4c7",
"assets/packages/country_pickers/assets/ar.png": "b8a60b09d7db59ca8e34d0c391f7cf47",
"assets/packages/country_pickers/assets/as.png": "d3ee7d8aeade5f87a5ab6ea1c53c1181",
"assets/packages/country_pickers/assets/at.png": "3d36c83a3d671b11f755c891bd8de687",
"assets/packages/country_pickers/assets/au.png": "63084e9484c0b6db451a1d68ad5adeb9",
"assets/packages/country_pickers/assets/aw.png": "01f11f497399c715de5f2561b93b8ef8",
"assets/packages/country_pickers/assets/ax.png": "adc1e135fd79d41a3d968de5ec048d8a",
"assets/packages/country_pickers/assets/az.png": "98833fec449ef8d633ef934e63080891",
"assets/packages/country_pickers/assets/ba.png": "4b5ad282e533a2e75d8b6ce8cff43db0",
"assets/packages/country_pickers/assets/bb.png": "8679327e664edb5e050982e7ee0c7828",
"assets/packages/country_pickers/assets/bd.png": "0ca802e7f67045161047607b20c6490e",
"assets/packages/country_pickers/assets/be.png": "6c7022eda06794dc916358268cb08d50",
"assets/packages/country_pickers/assets/bf.png": "5746b4e7bb2c86e7a2dc5077226b9952",
"assets/packages/country_pickers/assets/bg.png": "6b473783a5c5b427e668a2048022663e",
"assets/packages/country_pickers/assets/bh.png": "7533d290739c20bd2d0250414a74c19d",
"assets/packages/country_pickers/assets/bi.png": "2c1d426f4b941b9638303c34145ba672",
"assets/packages/country_pickers/assets/bj.png": "04f9872301a332efdd91735631f3d438",
"assets/packages/country_pickers/assets/bl.png": "536f99fa693e6b52a21c67e983632092",
"assets/packages/country_pickers/assets/bm.png": "72e7fff10d3168e4c62bad5465598db0",
"assets/packages/country_pickers/assets/bn.png": "1f1c5a29f9a6fd77963f7bb3de5946c2",
"assets/packages/country_pickers/assets/bo.png": "74bac15d186993c09eecdde11876b401",
"assets/packages/country_pickers/assets/bq.png": "3649c177693bfee9c2fcc63c191a51f1",
"assets/packages/country_pickers/assets/br.png": "4d47e5b273c0043e76bfd6ac76c3e035",
"assets/packages/country_pickers/assets/bs.png": "0b6796dfa9a54bf9c6473a005cc7f687",
"assets/packages/country_pickers/assets/bt.png": "43e973113f8c57a5cd303a49b5f371da",
"assets/packages/country_pickers/assets/bv.png": "ae5d87669104732f61cca68d6bd10cbf",
"assets/packages/country_pickers/assets/bw.png": "d50ac293dc1f0534aedb989c8ded82c0",
"assets/packages/country_pickers/assets/by.png": "c5d14943250d54b4a630794c5648c687",
"assets/packages/country_pickers/assets/bz.png": "3b84100ca29a0bc77474677e9da6fc0f",
"assets/packages/country_pickers/assets/ca.png": "e20a51380b2da69353e3755edead340d",
"assets/packages/country_pickers/assets/cc.png": "5d1c266d4620dc7203023882a7b647e5",
"assets/packages/country_pickers/assets/cd.png": "f0b60b807ec62ebfc391ff50c79ec30e",
"assets/packages/country_pickers/assets/cf.png": "acb28ea1b07b24c3e4984a6698faef24",
"assets/packages/country_pickers/assets/cg.png": "502df6404e41cb76d033af895f34eb2c",
"assets/packages/country_pickers/assets/ch.png": "fe8519b23bed3b2e8669dac779c3cb55",
"assets/packages/country_pickers/assets/ci.png": "a490576a22f2c67f1d331cbc5098f5f1",
"assets/packages/country_pickers/assets/ck.png": "882bc3896cdd040757972bcbbf75e4bb",
"assets/packages/country_pickers/assets/cl.png": "6735e0e2d88c119e9ed1533be5249ef1",
"assets/packages/country_pickers/assets/cm.png": "12c2c677c148caa9f6464050ea5647eb",
"assets/packages/country_pickers/assets/cn.png": "26c512b86a77d796629adf61862475ac",
"assets/packages/country_pickers/assets/co.png": "37dbdf7ef835ea7ee2c1bdcf91e9a2bb",
"assets/packages/country_pickers/assets/cr.png": "40dc5bc52eb9391bd6d1bf895b107a65",
"assets/packages/country_pickers/assets/cu.png": "82ec98ab8b9832e6a182367a5dd16f93",
"assets/packages/country_pickers/assets/cv.png": "a5193806962944dad9ee6c9c91f5cf10",
"assets/packages/country_pickers/assets/cw.png": "7132ff340c5f3fef7f163b60f2c841e2",
"assets/packages/country_pickers/assets/cx.png": "d5a6ca51e490d03b06a647d652d3fdb0",
"assets/packages/country_pickers/assets/cy.png": "f63fce2edfbc2aac831d6934e82a336f",
"assets/packages/country_pickers/assets/cz.png": "9e16a631c6e170d3415c005061b1e5da",
"assets/packages/country_pickers/assets/de.png": "e2227152ece494eabbb6b184dfb9f9a9",
"assets/packages/country_pickers/assets/dj.png": "6816bcba85e0179c4c1fafb76f35cd93",
"assets/packages/country_pickers/assets/dk.png": "2f452388777897cd70a25b1295582938",
"assets/packages/country_pickers/assets/dm.png": "013b44702a8fb5773a0983085b0dc076",
"assets/packages/country_pickers/assets/do.png": "e625b779a26a0130150b0a5bafe24380",
"assets/packages/country_pickers/assets/dz.png": "7372cc9383ca55804d35ca60d09f2ab9",
"assets/packages/country_pickers/assets/ec.png": "746ed5202fb98b28f7031393c2b479da",
"assets/packages/country_pickers/assets/ee.png": "69e0ffbab999ade674a9b07db0ee3941",
"assets/packages/country_pickers/assets/eg.png": "97843ac1dffee8cf3b3e7b341a38893e",
"assets/packages/country_pickers/assets/eh.png": "f91039d93b511ab8baba3a6242f21359",
"assets/packages/country_pickers/assets/er.png": "300cbfb7dda5e2eea87e9b03660a6077",
"assets/packages/country_pickers/assets/es.png": "a290e5120fe89e60d72009815478d0d3",
"assets/packages/country_pickers/assets/et.png": "7bc0f7bd7b4c252b375fc5bd94fe6a3f",
"assets/packages/country_pickers/assets/eu.png": "38336a6139fea0f3e2daa5a135e70d1d",
"assets/packages/country_pickers/assets/fi.png": "3ccd69a842e55183415b7ea2c04b15c8",
"assets/packages/country_pickers/assets/fj.png": "7970a279e5034d20c73b904388df7cba",
"assets/packages/country_pickers/assets/fk.png": "d599200dd54a121ac54e4895f97f19b1",
"assets/packages/country_pickers/assets/fm.png": "03c6a315c3acedae9a51cb444c99be5e",
"assets/packages/country_pickers/assets/fo.png": "ccd988f6309e4245cfa36478e103fb9b",
"assets/packages/country_pickers/assets/fr.png": "4fa81d3430e630527b8c6987619e85dc",
"assets/packages/country_pickers/assets/ga.png": "7a9bd1b751a4c92c4a00897dbb973214",
"assets/packages/country_pickers/assets/gb-eng.png": "0d9f2a6775fd52b79e1d78eb1dda10cf",
"assets/packages/country_pickers/assets/gb-nir.png": "09af1c5f1433c02e97a95286ce24f4d4",
"assets/packages/country_pickers/assets/gb-sct.png": "d55a9a9d41e9dc61cbeef059519d5618",
"assets/packages/country_pickers/assets/gb-wls.png": "74e73d030683c21d2183d92025d11be9",
"assets/packages/country_pickers/assets/gb.png": "09af1c5f1433c02e97a95286ce24f4d4",
"assets/packages/country_pickers/assets/gd.png": "7d4b72f73674133acb00c0ea3959e16b",
"assets/packages/country_pickers/assets/ge.png": "3fb1b71b32fb6bbd4e757adba06ce216",
"assets/packages/country_pickers/assets/gf.png": "4004b2e3ec6c151fe4cb43e902280952",
"assets/packages/country_pickers/assets/gg.png": "0a697b4266f87119aeb8a2ffe3b15498",
"assets/packages/country_pickers/assets/gh.png": "b35464dca793fa33e51bf890b5f3d92b",
"assets/packages/country_pickers/assets/gi.png": "987d065705257febe56bdbe05a294749",
"assets/packages/country_pickers/assets/gl.png": "fb536122819fd1d3fc18c02c7df93865",
"assets/packages/country_pickers/assets/gm.png": "be81263dd47ca1f99936f78f6b5dfc4a",
"assets/packages/country_pickers/assets/gn.png": "30b014c10d88f166e4bfd46bbc235ebe",
"assets/packages/country_pickers/assets/gp.png": "4fa81d3430e630527b8c6987619e85dc",
"assets/packages/country_pickers/assets/gq.png": "de93250a1de5e482f88bc5309ce21ac0",
"assets/packages/country_pickers/assets/gr.png": "ed1304c7d8e6a64f31e7b65c4beea944",
"assets/packages/country_pickers/assets/gs.png": "191d4b79605c08effa5b3def9834c9d6",
"assets/packages/country_pickers/assets/gt.png": "2791b68757cd31e89af8817817e589f0",
"assets/packages/country_pickers/assets/gu.png": "7e51aa7e3adaf526a8722350e0477192",
"assets/packages/country_pickers/assets/gw.png": "806f63c256bddd4f1680529f054f4043",
"assets/packages/country_pickers/assets/gy.png": "64f3007da6bdc84a25d8ad6b5d7f3094",
"assets/packages/country_pickers/assets/hk.png": "69a77d8a25952f39fe6aadafb6f7efc2",
"assets/packages/country_pickers/assets/hm.png": "63084e9484c0b6db451a1d68ad5adeb9",
"assets/packages/country_pickers/assets/hn.png": "5fcf2451994a42af2bba0c17717ed13f",
"assets/packages/country_pickers/assets/hr.png": "3175463c3e7e42116d5b59bc1da19a3f",
"assets/packages/country_pickers/assets/ht.png": "a49a27479ed8be33d962898febc049f1",
"assets/packages/country_pickers/assets/hu.png": "ff1d0e2bc549da022f2312c4ac7ca109",
"assets/packages/country_pickers/assets/id.png": "80bb82d11d5bc144a21042e77972bca9",
"assets/packages/country_pickers/assets/ie.png": "1d91912afc591dd120b47b56ea78cdbf",
"assets/packages/country_pickers/assets/il.png": "ee933479696b8c80d2ade96ee344a89c",
"assets/packages/country_pickers/assets/im.png": "d3da8affefefe4ec55770c9f3f43f117",
"assets/packages/country_pickers/assets/in.png": "0f1b94cf838fa1b86c172da4ab3db7e1",
"assets/packages/country_pickers/assets/io.png": "d4910e28f0164bc879999c17024d543c",
"assets/packages/country_pickers/assets/iq.png": "9434c17a6d4653df965e3276137764a1",
"assets/packages/country_pickers/assets/ir.png": "5d8864e2235f7acb3063a9f32684c80e",
"assets/packages/country_pickers/assets/is.png": "9fce179e688579504fb8210c51aed66d",
"assets/packages/country_pickers/assets/it.png": "ff7064f6e37512ff41e665f3a4987e70",
"assets/packages/country_pickers/assets/je.png": "6fcdb123f8bf3cafea5537542018b151",
"assets/packages/country_pickers/assets/jm.png": "87dbf861e528586787fdf8b6617e2f61",
"assets/packages/country_pickers/assets/jo.png": "79a73b63a1e0d78a08da882b146a2224",
"assets/packages/country_pickers/assets/jp.png": "fc7c3eb4c199252dc35730939ca4384d",
"assets/packages/country_pickers/assets/ke.png": "3e54059985907a758bb0531a711522fb",
"assets/packages/country_pickers/assets/kg.png": "e0eab32f37a96e43df134e70db49482a",
"assets/packages/country_pickers/assets/kh.png": "25e4099457402866cc1fabcd4506c6cc",
"assets/packages/country_pickers/assets/ki.png": "a93bda4f0f004d9c865f93d25c81ce18",
"assets/packages/country_pickers/assets/km.png": "c631326a464f21c51fbfd767be9bcf39",
"assets/packages/country_pickers/assets/kn.png": "11889e3432a57b8327eaeb5f34951db5",
"assets/packages/country_pickers/assets/kp.png": "8fcc8f2fc646b484b4a47cdc0ff21cab",
"assets/packages/country_pickers/assets/kr.png": "f36e020411beb5d89c1accce5acb1dd1",
"assets/packages/country_pickers/assets/kw.png": "cac0e665bc61366ffeb1cb08c24b609b",
"assets/packages/country_pickers/assets/ky.png": "bacc27cd23c1e359244533ecb9043de6",
"assets/packages/country_pickers/assets/kz.png": "caba66830ed539d3f86431ddf4006e72",
"assets/packages/country_pickers/assets/la.png": "ab542ca6e9c4e1911e70cb6178dd64a6",
"assets/packages/country_pickers/assets/lb.png": "30e7e0ee297d535bed953d7ad3321c6f",
"assets/packages/country_pickers/assets/lc.png": "32e5433954c7a99cd53c1e67f2ac604a",
"assets/packages/country_pickers/assets/li.png": "1abb7f4421487e6f40007c97ccf98c3d",
"assets/packages/country_pickers/assets/lk.png": "b7ab4259e284bb6f4f30cb8ec5e9b1b6",
"assets/packages/country_pickers/assets/lr.png": "ef37f094c6b37fbd2343bc800b2a35e5",
"assets/packages/country_pickers/assets/ls.png": "2bca756f9313957347404557acb532b0",
"assets/packages/country_pickers/assets/lt.png": "d79eb564dd857c66ddd65a41f4cdfe4e",
"assets/packages/country_pickers/assets/lu.png": "31349218e6c2a6e900a3a83baa8f61d2",
"assets/packages/country_pickers/assets/lv.png": "4370f6f09eecc21db000bd09191f3ff3",
"assets/packages/country_pickers/assets/ly.png": "c6d7280c521faa563e07b1f8bec1d9b7",
"assets/packages/country_pickers/assets/ma.png": "2302b44a7fe96ca595ea9528271a1ad9",
"assets/packages/country_pickers/assets/mc.png": "6375a336b1fd53d0e918ae945523078c",
"assets/packages/country_pickers/assets/md.png": "d579fff3f3b7644d54cdad3fbcdd501e",
"assets/packages/country_pickers/assets/me.png": "a2ca2c8d5567775b6f00634bcdb7a6f9",
"assets/packages/country_pickers/assets/mf.png": "4fa81d3430e630527b8c6987619e85dc",
"assets/packages/country_pickers/assets/mg.png": "0ef6271ad284ebc0069ff0aeb5a3ad1e",
"assets/packages/country_pickers/assets/mh.png": "575772c6fb22f9d6e470c627cacb737e",
"assets/packages/country_pickers/assets/mk.png": "b84591fe5860ed7accf9ff7e7307f099",
"assets/packages/country_pickers/assets/ml.png": "82bf0ca0c67d2371207a540b40c320fc",
"assets/packages/country_pickers/assets/mm.png": "0073e71d8d7d5c7f6ee70c828be1b7c8",
"assets/packages/country_pickers/assets/mn.png": "22d7616bc740394c5ae5b384bf2ef225",
"assets/packages/country_pickers/assets/mo.png": "08f0124b030743d010253d0108ef3b7f",
"assets/packages/country_pickers/assets/mp.png": "895e2aea9e8a9fb4a3db09ba75b2ae11",
"assets/packages/country_pickers/assets/mq.png": "394a6076943d6eb57ee10c7f2e044e1c",
"assets/packages/country_pickers/assets/mr.png": "253fc7fdd3d3360dfd2e8d726a3c27f7",
"assets/packages/country_pickers/assets/ms.png": "438b3ae48465543239a679ef915378de",
"assets/packages/country_pickers/assets/mt.png": "2c20ed4b1721ad71677d7e26f95425cd",
"assets/packages/country_pickers/assets/mu.png": "f00d3c927769eaf3bbc4d2c249ea3418",
"assets/packages/country_pickers/assets/mv.png": "8468c7f25a4b5dc7403146da72bd8126",
"assets/packages/country_pickers/assets/mw.png": "47fb9232df51b3a1de93fda80a795163",
"assets/packages/country_pickers/assets/mx.png": "7e557bb1bf47d52b6f3820e647fa5f98",
"assets/packages/country_pickers/assets/my.png": "e7fc1cb576089cfed2e7fa8071af4cd8",
"assets/packages/country_pickers/assets/mz.png": "3bce789f6780525f09212b677239f2d5",
"assets/packages/country_pickers/assets/na.png": "2431d5e2158f15bbcbad8e57bb78f25d",
"assets/packages/country_pickers/assets/nc.png": "b94385d139bf8b82b5b3f20559feece5",
"assets/packages/country_pickers/assets/ne.png": "89c2cbd76d15ae5c43f814b5ef5010dd",
"assets/packages/country_pickers/assets/nf.png": "4a9944f819ff0fc923f619184ae3c6df",
"assets/packages/country_pickers/assets/ng.png": "eeb857562b3dfcd105aef9ec371a916f",
"assets/packages/country_pickers/assets/ni.png": "41e2831687e9997fa4d5f4eb0700cc84",
"assets/packages/country_pickers/assets/nl.png": "3649c177693bfee9c2fcc63c191a51f1",
"assets/packages/country_pickers/assets/no.png": "ae5d87669104732f61cca68d6bd10cbf",
"assets/packages/country_pickers/assets/np.png": "99ba0ec8de01de3bc62146b2ffd1f96e",
"assets/packages/country_pickers/assets/nr.png": "c96262cfab530f60649c118ad21ab65f",
"assets/packages/country_pickers/assets/nu.png": "146c66c2ede3bd38ec680f76ef6525a0",
"assets/packages/country_pickers/assets/nz.png": "d22c137d0038c20c1fa98ae2ed3729b0",
"assets/packages/country_pickers/assets/om.png": "b16ebc34417eb7a6ad7ed0e3c79a71c0",
"assets/packages/country_pickers/assets/pa.png": "3215dc6016afeb373faacc38ee34b3d4",
"assets/packages/country_pickers/assets/pe.png": "b722a28a444000bab6cd03e859112e42",
"assets/packages/country_pickers/assets/pf.png": "33211a88528a8f7369d4bf92766131b2",
"assets/packages/country_pickers/assets/pg.png": "96c8233f13b1f4e7200d6ac4173de697",
"assets/packages/country_pickers/assets/ph.png": "158bd50b6f2d18f398e8600f6663b488",
"assets/packages/country_pickers/assets/pk.png": "c341fe3cf9392ed6a3b178269c1d9f0c",
"assets/packages/country_pickers/assets/pl.png": "e8714e9460929665055f1c93dce1bf61",
"assets/packages/country_pickers/assets/pm.png": "4fa81d3430e630527b8c6987619e85dc",
"assets/packages/country_pickers/assets/pn.png": "0205d0644f1207674c80eef7719db270",
"assets/packages/country_pickers/assets/pr.png": "b496188f51424a776d7ce5d8e28fd022",
"assets/packages/country_pickers/assets/ps.png": "e3e006d28f6b72169c717c1dba49b4d5",
"assets/packages/country_pickers/assets/pt.png": "1fe8c12d96a7536b0aa25a9ca7d3c701",
"assets/packages/country_pickers/assets/pw.png": "5216b69d6d8cb4e50962f8a6531231e8",
"assets/packages/country_pickers/assets/py.png": "4dca66b598604fb3af9dee2fd9622ac4",
"assets/packages/country_pickers/assets/qa.png": "3ed06ed4f403488dd34a747d2869204d",
"assets/packages/country_pickers/assets/re.png": "4fa81d3430e630527b8c6987619e85dc",
"assets/packages/country_pickers/assets/ro.png": "50ada15f78e9828d9886505e0087cbfd",
"assets/packages/country_pickers/assets/rs.png": "0a4c07a0ac5523d6328ab7d162d79d1e",
"assets/packages/country_pickers/assets/ru.png": "6974dcb42ad7eb3add1009ea0c6003e3",
"assets/packages/country_pickers/assets/rw.png": "f6602a0993265061713f34e8a86c42cf",
"assets/packages/country_pickers/assets/sa.png": "60851afd0246c77b57f76f32e853c130",
"assets/packages/country_pickers/assets/sb.png": "12cccb421defca5c7a4d19661f98f06f",
"assets/packages/country_pickers/assets/sc.png": "fce9893562cbe99d2e62a46b03e42007",
"assets/packages/country_pickers/assets/sd.png": "40572a05b7cd8ea53cee59c6be331588",
"assets/packages/country_pickers/assets/se.png": "775da17dccf0768a1f10f21d47942985",
"assets/packages/country_pickers/assets/sg.png": "fd3e4861be787cfde6338870e2c8d60a",
"assets/packages/country_pickers/assets/sh.png": "09af1c5f1433c02e97a95286ce24f4d4",
"assets/packages/country_pickers/assets/si.png": "9fa57dc95640bcd67051d7ff63caa828",
"assets/packages/country_pickers/assets/sj.png": "ae5d87669104732f61cca68d6bd10cbf",
"assets/packages/country_pickers/assets/sk.png": "207097f7d7d1ab9c7c88d16129cdba39",
"assets/packages/country_pickers/assets/sl.png": "61b9d992c8a6a83abc4d432069617811",
"assets/packages/country_pickers/assets/sm.png": "8615f3e38ee216e53895ac9acd31a56b",
"assets/packages/country_pickers/assets/sn.png": "1e8f55378ddd44cdc9868a7d35bda2fe",
"assets/packages/country_pickers/assets/so.png": "2a29df9dfbfbe10d886f1f6157557147",
"assets/packages/country_pickers/assets/sr.png": "b9e4b7fff662b655ce2b41324a04526b",
"assets/packages/country_pickers/assets/ss.png": "bfc79aa44e6d2b026717f7aae4431639",
"assets/packages/country_pickers/assets/st.png": "5abecf1202ef9f7b33bdb9d0e3913f80",
"assets/packages/country_pickers/assets/sv.png": "abe677facaeee030a10987f87831ee53",
"assets/packages/country_pickers/assets/sx.png": "aee87f6ff085fccd57c234f10a6d6052",
"assets/packages/country_pickers/assets/sy.png": "f415bf216f4c08b9a224b83165decc11",
"assets/packages/country_pickers/assets/sz.png": "a06f0fa489d9c9faf0690673242005d2",
"assets/packages/country_pickers/assets/tc.png": "0faabda1411738e572144aaeed24aadd",
"assets/packages/country_pickers/assets/td.png": "343a6c8ad0d15e0a7f44e075dd02082a",
"assets/packages/country_pickers/assets/tf.png": "cc0d9468b31855b29f38ca53eb522067",
"assets/packages/country_pickers/assets/tg.png": "a0f14f046b0356221c6923203bd43373",
"assets/packages/country_pickers/assets/th.png": "aa978ab62657076b0fa36ef0514d4dcf",
"assets/packages/country_pickers/assets/tj.png": "a9e427318b756c0c03bec3f3ff976fa3",
"assets/packages/country_pickers/assets/tk.png": "fcbceb6da21d71232ad719d05b6bb71b",
"assets/packages/country_pickers/assets/tl.png": "5519f1e7173e1f345d1580bab1b34d51",
"assets/packages/country_pickers/assets/tm.png": "9b27cae273a82e046c82a94f380826a6",
"assets/packages/country_pickers/assets/tn.png": "c375381bbdb31c4e80af18210d196d30",
"assets/packages/country_pickers/assets/to.png": "1cdd716b5b5502f85d6161dac6ee6c5b",
"assets/packages/country_pickers/assets/tr.png": "0a832c3bc7481e6b285dabbf1a119e22",
"assets/packages/country_pickers/assets/tt.png": "2633904bd4718afeecfa0503057a7f65",
"assets/packages/country_pickers/assets/tv.png": "d45cf6c6f6ec53ae9b52f77848dc6bf9",
"assets/packages/country_pickers/assets/tw.png": "079535fcbc6e855a85c508c9d1b5615a",
"assets/packages/country_pickers/assets/tz.png": "f8da3c6c3c64726ba9cb58ccfb373de2",
"assets/packages/country_pickers/assets/ua.png": "b4b10d893611470661b079cb30473871",
"assets/packages/country_pickers/assets/ug.png": "3a85e25a9797f7923a898007b727216a",
"assets/packages/country_pickers/assets/um.png": "b2b35d5b34ba0d66fda92e2003cd6b10",
"assets/packages/country_pickers/assets/us.png": "b2b35d5b34ba0d66fda92e2003cd6b10",
"assets/packages/country_pickers/assets/uy.png": "2579723aba2ee05a8d68c9084eaf5588",
"assets/packages/country_pickers/assets/uz.png": "475189379e4a67b29e9ab9a1d71f3cdd",
"assets/packages/country_pickers/assets/va.png": "e84a6f9dc08930a11d1e4b9d25b6234f",
"assets/packages/country_pickers/assets/vc.png": "e6cead4282ee9e362c624b46752aa3d5",
"assets/packages/country_pickers/assets/ve.png": "c177b253feaa781aae0368ae9d55d702",
"assets/packages/country_pickers/assets/vg.png": "420edc09fba1878f87336f8ebcdcee66",
"assets/packages/country_pickers/assets/vi.png": "bfe5691810c27983346bf52eb5149bb4",
"assets/packages/country_pickers/assets/vn.png": "32ff65ccbf31a707a195be2a5141a89b",
"assets/packages/country_pickers/assets/vu.png": "47ba92e2fe9961be0991dc76520dade9",
"assets/packages/country_pickers/assets/wf.png": "6214b3091dbe62c7a6c9991ee6466859",
"assets/packages/country_pickers/assets/ws.png": "d8e4ad3af401330e3f11db4be39dbf32",
"assets/packages/country_pickers/assets/xk.png": "6781f6c7e81d5617769900576c85917e",
"assets/packages/country_pickers/assets/ye.png": "4cf73209d90e9f02ead1565c8fdf59e5",
"assets/packages/country_pickers/assets/yt.png": "4fa81d3430e630527b8c6987619e85dc",
"assets/packages/country_pickers/assets/za.png": "6c93cf2398f55956549f241ef9f32e15",
"assets/packages/country_pickers/assets/zm.png": "e918e6d9756449e9e9fefd52faa0da80",
"assets/packages/country_pickers/assets/zw.png": "6245bb368a8a37c49f2e87331424c1fa",
"assets/packages/fluttertoast/assets/toastify.css": "a85675050054f179444bc5ad70ffc635",
"assets/packages/fluttertoast/assets/toastify.js": "e7006a0a033d834ef9414d48db3be6fc",
"assets/packages/linkedin_login/assets/linked_in_logo.png": "86b61ef3acce4c1f108238e2ea4f5d1c",
"assets/shaders/ink_sparkle.frag": "3b8911a587cf705d8852e10a60ef1d63",
"canvaskit/canvaskit.js": "2bc454a691c631b07a9307ac4ca47797",
"canvaskit/canvaskit.wasm": "bf50631470eb967688cca13ee181af62",
"canvaskit/profiling/canvaskit.js": "38164e5a72bdad0faa4ce740c9b8e564",
"canvaskit/profiling/canvaskit.wasm": "95a45378b69e77af5ed2bc72b2209b94",
"favicon.png": "bcd778f8117fd95edeebaa5c9fc101e5",
"firebase-messaging-sw.js": "9119cc4fcf5e024a8282b5d477bab6f7",
"flutter.js": "f85e6fb278b0fd20c349186fb46ae36d",
"google_drive.js": "a59619f7426604c0b43a42cc7d995f68",
"icons/Icon-192.png": "ac9a721a12bbc803b44f645561ecb1e1",
"icons/Icon-512.png": "96e752610906ba2a93c65f8abe1645f1",
"index.html": "ef4cdc376670688015128be5ebab6e6e",
"/": "ef4cdc376670688015128be5ebab6e6e",
"main.dart.js": "87b41022760d253d84bc5ea5669000c7",
"manifest.json": "b6a7de4a46d4c06c96c6e25d9ede313e",
"og_image.png": "ae553530715fb558c28f8030a1826aa3",
"progress_logo.gif": "02adc112155eee6f5cfa111061cdc56a",
"pusher.worker.min.js": "3af579f02ea96064aaec619d10419331",
"version.json": "6323302ca282538b922ee3b6d91acaad",
"voice_download.gif": "6d1585bf7b168658f620120ec58aae91"
};

// The application shell files that are downloaded before a service worker can
// start.
const CORE = [
  "main.dart.js",
"index.html",
"assets/AssetManifest.json",
"assets/FontManifest.json"];
// During install, the TEMP cache is populated with the application shell files.
self.addEventListener("install", (event) => {
  self.skipWaiting();
  return event.waitUntil(
    caches.open(TEMP).then((cache) => {
      return cache.addAll(
        CORE.map((value) => new Request(value, {'cache': 'reload'})));
    })
  );
});

// During activate, the cache is populated with the temp files downloaded in
// install. If this service worker is upgrading from one with a saved
// MANIFEST, then use this to retain unchanged resource files.
self.addEventListener("activate", function(event) {
  return event.waitUntil(async function() {
    try {
      var contentCache = await caches.open(CACHE_NAME);
      var tempCache = await caches.open(TEMP);
      var manifestCache = await caches.open(MANIFEST);
      var manifest = await manifestCache.match('manifest');
      // When there is no prior manifest, clear the entire cache.
      if (!manifest) {
        await caches.delete(CACHE_NAME);
        contentCache = await caches.open(CACHE_NAME);
        for (var request of await tempCache.keys()) {
          var response = await tempCache.match(request);
          await contentCache.put(request, response);
        }
        await caches.delete(TEMP);
        // Save the manifest to make future upgrades efficient.
        await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
        return;
      }
      var oldManifest = await manifest.json();
      var origin = self.location.origin;
      for (var request of await contentCache.keys()) {
        var key = request.url.substring(origin.length + 1);
        if (key == "") {
          key = "/";
        }
        // If a resource from the old manifest is not in the new cache, or if
        // the MD5 sum has changed, delete it. Otherwise the resource is left
        // in the cache and can be reused by the new service worker.
        if (!RESOURCES[key] || RESOURCES[key] != oldManifest[key]) {
          await contentCache.delete(request);
        }
      }
      // Populate the cache with the app shell TEMP files, potentially overwriting
      // cache files preserved above.
      for (var request of await tempCache.keys()) {
        var response = await tempCache.match(request);
        await contentCache.put(request, response);
      }
      await caches.delete(TEMP);
      // Save the manifest to make future upgrades efficient.
      await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
      return;
    } catch (err) {
      // On an unhandled exception the state of the cache cannot be guaranteed.
      console.error('Failed to upgrade service worker: ' + err);
      await caches.delete(CACHE_NAME);
      await caches.delete(TEMP);
      await caches.delete(MANIFEST);
    }
  }());
});

// The fetch handler redirects requests for RESOURCE files to the service
// worker cache.
self.addEventListener("fetch", (event) => {
  if (event.request.method !== 'GET') {
    return;
  }
  var origin = self.location.origin;
  var key = event.request.url.substring(origin.length + 1);
  // Redirect URLs to the index.html
  if (key.indexOf('?v=') != -1) {
    key = key.split('?v=')[0];
  }
  if (event.request.url == origin || event.request.url.startsWith(origin + '/#') || key == '') {
    key = '/';
  }
  // If the URL is not the RESOURCE list then return to signal that the
  // browser should take over.
  if (!RESOURCES[key]) {
    return;
  }
  // If the URL is the index.html, perform an online-first request.
  if (key == '/') {
    return onlineFirst(event);
  }
  event.respondWith(caches.open(CACHE_NAME)
    .then((cache) =>  {
      return cache.match(event.request).then((response) => {
        // Either respond with the cached resource, or perform a fetch and
        // lazily populate the cache only if the resource was successfully fetched.
        return response || fetch(event.request).then((response) => {
          if (response && Boolean(response.ok)) {
            cache.put(event.request, response.clone());
          }
          return response;
        });
      })
    })
  );
});

self.addEventListener('message', (event) => {
  // SkipWaiting can be used to immediately activate a waiting service worker.
  // This will also require a page refresh triggered by the main worker.
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
    return;
  }
  if (event.data === 'downloadOffline') {
    downloadOffline();
    return;
  }
});

// Download offline will check the RESOURCES for all files not in the cache
// and populate them.
async function downloadOffline() {
  var resources = [];
  var contentCache = await caches.open(CACHE_NAME);
  var currentContent = {};
  for (var request of await contentCache.keys()) {
    var key = request.url.substring(origin.length + 1);
    if (key == "") {
      key = "/";
    }
    currentContent[key] = true;
  }
  for (var resourceKey of Object.keys(RESOURCES)) {
    if (!currentContent[resourceKey]) {
      resources.push(resourceKey);
    }
  }
  return contentCache.addAll(resources);
}

// Attempt to download the resource online before falling back to
// the offline cache.
function onlineFirst(event) {
  return event.respondWith(
    fetch(event.request).then((response) => {
      return caches.open(CACHE_NAME).then((cache) => {
        cache.put(event.request, response.clone());
        return response;
      });
    }).catch((error) => {
      return caches.open(CACHE_NAME).then((cache) => {
        return cache.match(event.request).then((response) => {
          if (response != null) {
            return response;
          }
          throw error;
        });
      });
    })
  );
}
